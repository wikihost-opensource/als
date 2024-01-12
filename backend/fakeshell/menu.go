package fakeshell

import (
	"fmt"
	"os/exec"

	"github.com/reeflective/console"
	"github.com/samlm0/als/v2/config"
	"github.com/samlm0/als/v2/fakeshell/commands"
	"github.com/spf13/cobra"
)

func defineMenuCommands(a *console.Console) console.Commands {
	showedIsFirstTime := false
	return func() *cobra.Command {
		rootCmd := &cobra.Command{}

		rootCmd.InitDefaultHelpCmd()
		rootCmd.CompletionOptions.DisableDefaultCmd = true
		rootCmd.DisableFlagsInUseLine = true

		features := map[string]bool{
			"ping":       config.Config.FeaturePing,
			"traceroute": config.Config.FeatureTraceroute,
			"nexttrace":  config.Config.FeatureTraceroute,
			"mtr":        config.Config.FeatureMTR,
		}

		hasNotFound := false
		for command, feature := range features {
			if feature {
				_, err := exec.LookPath(command)
				if err != nil {
					if !showedIsFirstTime {
						fmt.Println("Error: " + command + " is not install")
					}
					hasNotFound = true
					continue
				}
				commands.AddExecureableAsCommand(rootCmd, command)
			}
		}

		if hasNotFound {
			showedIsFirstTime = true
		}

		rootCmd.SetHelpCommand(&cobra.Command{
			Use:    "no-help",
			Hidden: true,
		})

		return rootCmd
	}
}
