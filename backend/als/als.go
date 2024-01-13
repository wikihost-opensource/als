package als

import (
	"log"

	"github.com/samlm0/als/v2/als/client"
	"github.com/samlm0/als/v2/als/timer"
	"github.com/samlm0/als/v2/config"
	alsHttp "github.com/samlm0/als/v2/http"
)

func Init() {
	aHttp := alsHttp.CreateServer()

	log.Default().Println("Listen on: " + config.Config.ListenHost + ":" + config.Config.ListenPort)
	aHttp.SetListen(config.Config.ListenHost + ":" + config.Config.ListenPort)

	SetupHttpRoute(aHttp.GetEngine())

	if config.Config.FeatureIfaceTraffic {
		go timer.SetupInterfaceBroadcast()
	}
	go timer.UpdateSystemResource()
	go client.HandleQueue()
	aHttp.Start()
}
