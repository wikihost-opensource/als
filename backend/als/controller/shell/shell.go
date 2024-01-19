package shell

import (
	"context"
	"fmt"
	"net/http"
	"os"
	"os/exec"
	"strconv"
	"strings"

	"github.com/creack/pty"
	"github.com/gin-gonic/gin"
	"github.com/gorilla/websocket"
	"github.com/samlm0/als/v2/als/client"
)

var upgrader = websocket.Upgrader{
	ReadBufferSize:  4096,
	WriteBufferSize: 4096,
}

func HandleNewShell(c *gin.Context) {
	upgrader.CheckOrigin = func(r *http.Request) bool { return true }
	conn, err := upgrader.Upgrade(c.Writer, c.Request, nil)
	if err != nil {
		fmt.Println(err)
		return
	}
	defer conn.Close()
	v, _ := c.Get("clientSession")
	clientSession := v.(*client.ClientSession)
	handleNewConnection(conn, clientSession, c)
}

func handleNewConnection(conn *websocket.Conn, session *client.ClientSession, ginC *gin.Context) {
	ctx, cancel := context.WithCancel(session.GetContext(ginC.Request.Context()))
	defer cancel()

	ex, _ := os.Executable()
	c := exec.Command(ex, "--shell")
	ptmx, err := pty.Start(c)
	if err != nil {
		return
	}
	defer ptmx.Close()

	// context aware
	go func() {
		<-ctx.Done()
		if c.Process != nil {
			c.Process.Kill()
		}
	}()

	// cmd -> websocket
	go func() {
		defer cancel()
		buf := make([]byte, 4096)
		for {
			n, err := ptmx.Read(buf)
			if err != nil {
				break
			}
			conn.WriteMessage(websocket.BinaryMessage, buf[:n])
		}
	}()

	// websocket -> cmd
	go func() {
		defer cancel()
		for {
			_, buf, err := conn.ReadMessage()
			if err != nil {
				break
			}
			index := string(buf[:1])
			switch index {
			case "1":
				// normal input
				ptmx.Write(buf[1:])
			case "2":
				// win resize
				args := strings.Split(string(buf[1:]), ";")
				h, _ := strconv.Atoi(args[0])
				w, _ := strconv.Atoi(args[1])
				pty.Setsize(ptmx, &pty.Winsize{
					Rows: uint16(h),
					Cols: uint16(w),
				})
			}
		}
	}()
	c.Wait()
}
