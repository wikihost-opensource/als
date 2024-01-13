package timer

import (
	"runtime"
	"strconv"
	"time"

	"github.com/samlm0/als/v2/als/client"
)

func UpdateSystemResource() {
	var m runtime.MemStats
	ticker := time.NewTicker(5 * time.Second)
	for {
		<-ticker.C
		runtime.ReadMemStats(&m)
		client.BroadCastMessage("MemoryUsage", strconv.Itoa(int(m.Sys)))
	}

}
