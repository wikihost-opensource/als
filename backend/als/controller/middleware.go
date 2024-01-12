package controller

import (
	"github.com/gin-gonic/gin"
	"github.com/samlm0/als/v2/als/client"
)

func MiddlewareSessionOnHeader() gin.HandlerFunc {
	return func(c *gin.Context) {
		sessionId := c.GetHeader("session")
		client, ok := client.Clients[sessionId]
		if !ok {
			c.JSON(400, &gin.H{
				"success": false,
				"error":   "Invaild session",
			})
			c.Abort()
			return
		}
		c.Set("clientSession", client)
		c.Next()
	}
}

func MiddlewareSessionOnUrl() gin.HandlerFunc {
	return func(c *gin.Context) {
		sessionId := c.Param("session")
		client, ok := client.Clients[sessionId]
		if !ok {
			c.JSON(400, &gin.H{
				"success": false,
				"error":   "Invaild session",
			})
			c.Abort()
			return
		}
		c.Set("clientSession", client)
		c.Next()
	}
}
