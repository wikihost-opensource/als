package config

import (
	"context"
	"fmt"
	"io"
	"log"
	"net"
	"net/http"

	"github.com/miekg/dns"
)

func updatePublicIP() {
	log.Default().Println("Updating IP address from internet...")
	// get ipv4
	go func() {
		addr, err := getPublicIPv4ViaDNS()
		if err == nil {
			Config.PublicIPv4 = addr
			log.Printf("Public IPv4 address: %s\n", addr)
			// fmt.Println(Config)
			return
		}

		addr, err = getPublicIPv4ViaHttp()
		if err == nil {
			Config.PublicIPv4 = addr
			log.Printf("Public IPv4 address: %s\n", addr)
			return
		}
	}()

	// get ipv6
	go func() {
		addr, err := getPublicIPv6ViaDNS()
		if err == nil {
			Config.PublicIPv6 = addr
			log.Printf("Public IPv6 address: %s\n", addr)
			return
		}
	}()
}

func getPublicIPv4ViaDNS() (string, error) {
	m := new(dns.Msg)
	m.SetQuestion("myip.opendns.com.", dns.TypeA)

	in, err := dns.Exchange(m, "resolver1.opendns.com:53")
	if err != nil {
		return "", err
	}

	if len(in.Answer) < 1 {
		return "", fmt.Errorf("no answer")
	}

	record, ok := in.Answer[0].(*dns.A)
	if !ok {
		return "", fmt.Errorf("not A record")
	}
	return record.A.String(), nil
}

func getPublicIPv6ViaDNS() (string, error) {
	m := new(dns.Msg)
	m.SetQuestion("myip.opendns.com.", dns.TypeAAAA)

	in, err := dns.Exchange(m, "resolver1.opendns.com:53")
	if err != nil {
		return "", err
	}

	if len(in.Answer) < 1 {
		return "", fmt.Errorf("no answer")
	}

	record, ok := in.Answer[0].(*dns.AAAA)
	if !ok {
		return "", fmt.Errorf("not A record")
	}

	return record.AAAA.String(), nil
}

func getPublicIPViaHttp(client *http.Client) (string, error) {
	lists := []string{
		"https://myexternalip.com/raw",
		"https://ifconfig.co/ip",
	}

	for _, url := range lists {
		resp, err := client.Get(url)
		if err != nil {
			continue
		}

		body, err := io.ReadAll(resp.Body)
		if err != nil {
			return "", err
		}

		addr := net.ParseIP(string(body))
		if addr != nil {
			return addr.String(), nil
		}
	}

	return "", fmt.Errorf("no answer")
}

func getPublicIPv4ViaHttp() (string, error) {
	client := &http.Client{
		Transport: &http.Transport{
			DialContext: func(ctx context.Context, network, addr string) (net.Conn, error) {
				var dialer net.Dialer
				return dialer.DialContext(ctx, "tcp4", addr)
			},
		},
	}
	return getPublicIPViaHttp(client)
}
