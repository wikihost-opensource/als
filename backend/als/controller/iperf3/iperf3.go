package iperf3

import (
	"context"
	"fmt"
	"io"
	"math/rand"
	"os/exec"
	"strconv"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/samlm0/als/v2/als/client"
	"github.com/samlm0/als/v2/config"
)

func random(min, max int) int {
	rand.Seed(time.Now().UnixNano())
	return rand.Intn(max-min+1) + min
}

func Handle(c *gin.Context) {
	v, _ := c.Get("clientSession")
	clientSession := v.(*client.ClientSession)

	timeout := time.Second * 60
	port := random(config.Config.Iperf3StartPort, config.Config.Iperf3EndPort)

	ctx, cancel := context.WithTimeout(clientSession.GetContext(c.Request.Context()), timeout)
	defer cancel()

	cmd := exec.CommandContext(ctx, "iperf3", "-s", "--forceflush", "-p", fmt.Sprintf("%d", port))
	clientSession.Channel <- &client.Message{
		Name:    "Iperf3",
		Content: strconv.Itoa(port),
	}

	writer := func(pipe io.ReadCloser, err error) {
		if err != nil {
			return
		}
		for {
			buf := make([]byte, 1024)
			n, err := pipe.Read(buf)
			if err != nil {
				return
			}
			msg := &client.Message{
				Name:    "Iperf3Stream",
				Content: string(buf[:n]),
			}
			clientSession.Channel <- msg
		}
	}

	go writer(cmd.StdoutPipe())
	go writer(cmd.StderrPipe())

	err := cmd.Start()
	if err != nil {
		// 处理错误
		// fmt.Println("Error starting command:", err)
		c.JSON(400, &gin.H{
			"success": false,
		})
		return
	}

	cmd.Wait()

	c.JSON(200, &gin.H{
		"success": true,
	})
}
