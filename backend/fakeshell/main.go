package fakeshell

import (
	"io"
	"os"

	"github.com/reeflective/console"
)

func exitCtrlD(c *console.Console) {
	os.Exit(0)
}

func HandleConsole() {
	app := console.New("example")
	app.NewlineBefore = true
	app.NewlineAfter = true

	menu := app.ActiveMenu()
	setupPrompt(menu)

	// go func() {
	// 	sig := make(chan os.Signal)
	// 	signal.Notify(sig)
	// 	for s := range sig {
	// 		fmt.Println(s)
	// 	}
	// }()

	menu.AddInterrupt(io.EOF, exitCtrlD)
	menu.SetCommands(defineMenuCommands(app))
	app.Start()
}
