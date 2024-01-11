package fakeshell

import "github.com/reeflective/console"

func setupPrompt(m *console.Menu) {
	p := m.Prompt()

	p.Primary = func() string {
		prompt := "\x1b[33mALS\x1b[0m > "
		return prompt
	}

	p.Secondary = func() string { return ">" }
	p.Transient = func() string { return "\x1b[1;30m" + ">> " + "\x1b[0m" }
}
