package config

import (
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
)

func updateLocation() {
	log.Default().Println("Updating server location from internet...")

	resp, err := http.Get("https://ipapi.co/json/")
	if err != nil {
		return
	}
	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return
	}
	var data map[string]interface{}
	json.Unmarshal(body, &data)
	if _, ok := data["country_name"]; !ok {
		return
	}

	if _, ok := data["city"]; !ok {
		return
	}

	Config.Location = fmt.Sprintf("%s, %s", data["city"], data["country_name"])
	log.Default().Println("Server location: " + Config.Location)
	log.Default().Println("Updating server location from internet successed, from ipapi.co")
}
