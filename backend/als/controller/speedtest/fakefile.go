package speedtest

import (
	"crypto/rand"
	"fmt"
	"io"
	"regexp"
	"strconv"
	"strings"

	"github.com/gin-gonic/gin"
	"github.com/samlm0/als/v2/als/client"
	"github.com/samlm0/als/v2/config"
)

func contains(slice []string, item string) bool {
	for _, a := range slice {
		if a == item {
			return true
		}
	}
	return false
}

func sizeToBytes(size string) (int64, error) {
	re := regexp.MustCompile(`^(\d+)(KB|MB|GB|TB)$`)
	matches := re.FindStringSubmatch(size)

	if matches == nil {
		return 0, fmt.Errorf("invalid size format")
	}

	num, err := strconv.ParseInt(matches[1], 10, 64)
	if err != nil {
		return 0, err
	}

	switch strings.ToUpper(matches[2]) {
	case "KB":
		num *= 1024
	case "MB":
		num *= 1024 * 1024
	case "GB":
		num *= 1024 * 1024 * 1024
	case "TB":
		num *= 1024 * 1024 * 1024 * 1024
	}

	return num, nil
}

func HandleFakeFile(c *gin.Context) {
	filename := c.Param("filename")
	var re = regexp.MustCompile(`^(\d+)(KB|MB|GB|TB)\.test$`)

	pos := re.FindStringIndex(filename)
	if pos == nil {
		c.String(404, "404 file not found")
		return
	}

	client.WaitQueue(c.Request.Context(), nil)

	filename = filename[0 : len(filename)-5]
	if !contains(config.Config.SpeedtestFileList, filename) {
		c.String(404, "404 file not found")
		return
	}

	size, ok := sizeToBytes(filename)
	if ok != nil {
		c.String(404, "Invaild file size")
		return
	}
	c.Header("Content-Type", "application/octet-stream")
	c.Header("Content-Length", strconv.FormatInt(size, 10))
	c.Stream(func(w io.Writer) bool {
		buf := make([]byte, 1024*1024)
		rand.Read(buf)

		for size > 0 {
			// 如果剩余的大小小于缓冲区的大小，只写入剩余的大小
			if size < int64(len(buf)) {
				buf = buf[:size]
			}

			// 将缓冲区写入响应
			w.Write(buf)

			// 更新剩余的大小
			size -= int64(len(buf))
		}

		// 返回false表示我们已经完成了写入
		return false
	})
	// c.Data()
}
