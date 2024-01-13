package config

import (
	"log"
	"os"
	"strconv"
	"strings"
)

func LoadFromEnv() {
	envVarsString := map[string]*string{
		"LISTEN_IP":       &Config.ListenHost,
		"HTTP_PORT":       &Config.ListenPort,
		"LOCATION":        &Config.Location,
		"PUBLIC_IPV4":     &Config.PublicIPv4,
		"PUBLIC_IPV6":     &Config.PublicIPv6,
		"SPONSOR_MESSAGE": &Config.SponsorMessage,
	}

	envVarsInt := map[string]*int{
		"UTILITIES_IPERF3_PORT_MIN": &Config.Iperf3StartPort,
		"UTILITIES_IPERF3_PORT_MAX": &Config.Iperf3EndPort,
	}

	envVarsBool := map[string]*bool{
		"DISPLAY_TRAFFIC":           &Config.FeatureIfaceTraffic,
		"ENABLE_SPEEDTEST":          &Config.FeatureLibrespeed,
		"UTILITIES_SPEEDTESTDOTNET": &Config.FeatureSpeedtestDotNet,
		"UTILITIES_PING":            &Config.FeaturePing,
		"UTILITIES_FAKESHELL":       &Config.FeatureShell,
		"UTILITIES_IPERF3":          &Config.FeatureIperf3,
		"UTILITIES_MTR":             &Config.FeatureMTR,
	}

	for envVar, configField := range envVarsString {
		if v := os.Getenv(envVar); len(v) != 0 {
			*configField = v
		}
	}

	for envVar, configField := range envVarsInt {
		if v := os.Getenv(envVar); len(v) != 0 {
			v, err := strconv.Atoi(v)
			if err != nil {
				continue
			}
			*configField = v
		}
	}

	for envVar, configField := range envVarsBool {
		if v := os.Getenv(envVar); len(v) != 0 {
			*configField = v == "true"
		}
	}

	if v := os.Getenv("SPEEDTEST_FILE_LIST"); len(v) != 0 {
		fileLists := strings.Split(v, " ")
		Config.SpeedtestFileList = fileLists
	}

	if !IsInternalCall {
		log.Default().Println("Loading config from environment variables...")
	}
}
