package speedtest

import (
	"crypto/rand"
	"io"
	"net/http"
	"strconv"

	"github.com/gin-gonic/gin"
)

func HandleDownload(c *gin.Context) {
	c.Writer.WriteHeader(http.StatusOK)
	chunks := 4
	if ckSize, ok := c.GetQuery("ckSize"); ok {
		if ckSizeInt, err := strconv.Atoi(ckSize); err == nil && ckSizeInt > 0 {
			chunks = ckSizeInt
			if chunks > 1024 {
				chunks = 1024
			}
		}
	}

	data := make([]byte, 1048576)
	rand.Read(data)

	for i := 0; i < chunks; i++ {
		c.Writer.Write(data)
	}
	c.Writer.CloseNotify()
}

func HandleUpload(c *gin.Context) {
	c.Header("Cache-Control", "no-store, no-cache, must-revalidate, max-age=0, s-maxage=0, post-check=0, pre-check=0")
	c.Header("Pragma", "no-cache")
	c.Header("Connection", "keep-alive")
	_, err := io.Copy(io.Discard, c.Request.Body)
	if err != nil {
		c.Status(http.StatusBadRequest)
		return
	}
	_ = c.Request.Body.Close()

	c.Header("Connection", "keep-alive")
	c.Status(http.StatusOK)
}
