package speedtest

import (
	"context"
	"sync"
)

var queueLine = make(map[context.Context]context.CancelFunc, 0)
var queueLock = sync.Mutex{}
var hasQueueWorker = false
var queueStop = make(chan struct{})

func waitQueue(ctx context.Context) {

	queueCtx, cancel := context.WithCancel(ctx)
	queueLine[ctx] = cancel

LISTEN:
	queueLock.Lock()
	if !hasQueueWorker {
		hasQueueWorker = true
		go handleQueue()
	}
	queueLock.Unlock()

	select {
	case <-queueCtx.Done():
	case <-ctx.Done():
		return
	case <-queueStop:
		go handleQueue()
		goto LISTEN
	}
}

func handleQueue() {
	for ctx, notify := range queueLine {
		notify()
		<-ctx.Done()
		delete(queueLine, ctx)
	}
	hasQueueWorker = false
	<-queueStop
}
