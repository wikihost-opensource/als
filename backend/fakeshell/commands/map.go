package commands

import (
	"os"
	"os/exec"

	"github.com/spf13/cobra"
)

func AddExecureableAsCommand(cmd *cobra.Command, command string, argFilter func(args []string) ([]string, error)) {

	cmdDefine := &cobra.Command{
		Use: command,
		Run: func(cmd *cobra.Command, args []string) {
			args, err := argFilter(args)
			if err != nil {
				cmd.Println(err)
				return
			}
			c := exec.Command(command, args...)
			c.Env = os.Environ()
			c.Env = append(c.Env, "TERM=xterm-256color")
			c.Stdin = cmd.InOrStdin()
			c.Stdout = cmd.OutOrStdout()
			c.Stderr = cmd.OutOrStderr()

			c.Run()
			c.Wait()
		},
		DisableFlagParsing: true,
	}

	cmd.AddCommand(cmdDefine)
}
