package fakeshell

import (
	"github.com/reeflective/console"
	"github.com/samlm0/als/v2/config"
	"github.com/samlm0/als/v2/fakeshell/commands"
	"github.com/spf13/cobra"
)

func defineMenuCommands(a *console.Console) console.Commands {
	return func() *cobra.Command {
		rootCmd := &cobra.Command{}

		rootCmd.InitDefaultHelpCmd()
		rootCmd.CompletionOptions.DisableDefaultCmd = true
		rootCmd.DisableFlagsInUseLine = true

		features := map[string]bool{
			"ping":       config.Config.FeaturePing,
			"traceroute": config.Config.FeatureTraceroute,
			"mtr":        config.Config.FeatureMTR,
		}

		for command, feature := range features {
			if feature {
				commands.AddExecureableAsCommand(rootCmd, command)
			}
		}

		rootCmd.SetHelpCommand(&cobra.Command{
			Use:    "no-help",
			Hidden: true,
		})

		return rootCmd
	}
}
