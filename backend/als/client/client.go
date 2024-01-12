package client

import (
	"context"
)

var Clients = make(map[string]*ClientSession)

type Message struct {
	Name    string
	Content string
}

type ClientSession struct {
	Channel chan *Message
	ctx     context.Context
}

func (c *ClientSession) SetContext(ctx context.Context) {
	c.ctx = ctx
}

func (c *ClientSession) GetContext(requestCtx context.Context) context.Context {
	ctx, cancel := context.WithCancel(context.Background())

	go func() {
		select {
		case <-c.ctx.Done():
			cancel()
			break
		case <-requestCtx.Done():
			cancel()
			break
		}
	}()

	return ctx
}

func BroadCastMessage(name string, content string) {
	for _, client := range Clients {
		client.Channel <- &Message{
			Name:    name,
			Content: content,
		}
	}
}
