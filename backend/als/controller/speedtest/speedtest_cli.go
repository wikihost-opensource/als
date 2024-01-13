package speedtest

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"os/exec"
	"sync"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/samlm0/als/v2/als/client"
)

var count = 1
var lock = sync.Mutex{}

func fakeQueue() {
	go func() {
		lock.Lock()
		count++
		lock.Unlock()
		ctx, cancel := context.WithCancel(context.TODO())
		client.WaitQueue(ctx, nil)
		fmt.Println(count)
		time.Sleep(time.Duration(count) * time.Second)
		cancel()
	}()
}

func HandleSpeedtestDotNet(c *gin.Context) {
	nodeId, ok := c.GetQuery("node_id")
	v, _ := c.Get("clientSession")
	clientSession := v.(*client.ClientSession)
	if !ok {
		nodeId = ""
	}
	closed := false
	timeout := time.Second * 60
	count = 1
	ctx, cancel := context.WithTimeout(clientSession.GetContext(c.Request.Context()), timeout)
	defer func() {
		cancel()
		closed = true
	}()
	go func() {
		<-ctx.Done()
		closed = true
	}()
	client.WaitQueue(ctx, func() {
		pos, totalPos := client.GetQueuePostitionByCtx(ctx)
		msg, _ := json.Marshal(gin.H{"type": "queue", "pos": pos, "totalPos": totalPos})
		if !closed {
			clientSession.Channel <- &client.Message{
				Name:    "SpeedtestStream",
				Content: string(msg),
			}
		}
	})
	args := []string{"--accept-license", "-f", "jsonl"}
	if nodeId != "" {
		args = append(args, "-s", nodeId)
	}
	cmd := exec.Command("speedtest", args...)

	go func() {
		<-ctx.Done()
		if cmd.Process != nil {
			cmd.Process.Kill()
		}
	}()

	writer := func(pipe io.ReadCloser, err error) {
		if err != nil {
			fmt.Println("Pipe closed", err)
			return
		}
		for {
			buf := make([]byte, 1024)
			n, err := pipe.Read(buf)
			if err != nil {
				return
			}
			if !closed {
				clientSession.Channel <- &client.Message{
					Name:    "SpeedtestStream",
					Content: string(buf[:n]),
				}
			}
		}
	}

	go writer(cmd.StdoutPipe())
	go writer(cmd.StderrPipe())

	cmd.Run()
	fmt.Println("speedtest-cli quit")
	c.JSON(200, &gin.H{
		"success": true,
	})
}
