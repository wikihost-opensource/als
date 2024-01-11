package main

import (
	"flag"

	"github.com/samlm0/als/v2/als"
	"github.com/samlm0/als/v2/config"
	"github.com/samlm0/als/v2/fakeshell"
)

var shell = flag.Bool("shell", false, "Start as fake shell")

func main() {
	flag.Parse()
	if *shell {
		config.IsInternalCall = true
		config.Load()
		fakeshell.HandleConsole()
		return
	}

	config.LoadWebConfig()

	als.Init()
}
