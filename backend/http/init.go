package http

import (
	"github.com/gin-gonic/gin"
)

type Server struct {
	engine *gin.Engine
	listen string
}

func CreateServer() *Server {
	gin.SetMode(gin.ReleaseMode)
	e := &Server{
		engine: gin.Default(),
		listen: ":8080",
	}
	return e
}

func (e *Server) GetEngine() *gin.Engine {
	return e.engine
}

func (e *Server) SetListen(listen string) {
	e.listen = listen
}

func (e *Server) Start() {
	e.engine.Run(e.listen)
}
