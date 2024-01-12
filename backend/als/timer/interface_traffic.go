package timer

import (
	"net"
	"strconv"
	"strings"
	"time"

	"github.com/samlm0/als/v2/als/client"
	"github.com/vishvananda/netlink"
)

type InterfaceTrafficCache struct {
	InterfaceName string
	LastCacheTime time.Time
	Caches        [][3]uint64
	LastRx        uint64 `json:"-"`
	LastTx        uint64 `json:"-"`
}

var InterfaceCaches = make(map[int]*InterfaceTrafficCache)

func SetupInterfaceBroadcast() {
	ticker := time.NewTicker(1 * time.Second)
	for {
		<-ticker.C
		interfaces, err := net.Interfaces()
		if err != nil {
			continue
		}

		for _, iface := range interfaces {
			// skip down interface
			if iface.Flags&net.FlagUp == 0 {
				continue
			}

			// skip docker
			if strings.Index(iface.Name, "docker") == 0 {
				continue
			}

			// skip lo
			if iface.Name == "lo" {
				continue
			}

			// skip wireguard
			if strings.Index(iface.Name, "wt") == 0 {
				continue
			}

			// skip veth
			if strings.Index(iface.Name, "veth") == 0 {
				continue
			}

			link, err := netlink.LinkByIndex(iface.Index)
			if err != nil {
				continue
			}
			now := time.Now()
			cache, ok := InterfaceCaches[iface.Index]
			if !ok {
				InterfaceCaches[iface.Index] = &InterfaceTrafficCache{
					InterfaceName: iface.Name,
					LastCacheTime: now,
					Caches:        make([][3]uint64, 0),
					LastRx:        0,
					LastTx:        0,
				}
				cache = InterfaceCaches[iface.Index]
			}

			cache.LastRx = link.Attrs().Statistics.RxBytes
			cache.LastTx = link.Attrs().Statistics.TxBytes

			cache.Caches = append(cache.Caches, [3]uint64{uint64(now.Unix()), cache.LastRx, cache.LastTx})
			if len(cache.Caches) > 30 {
				cache.Caches = cache.Caches[len(cache.Caches)-30:]
			}
			cache.LastCacheTime = now
			client.BroadCastMessage("InterfaceTraffic", iface.Name+","+strconv.Itoa(int(now.Unix()))+","+strconv.Itoa(int(cache.LastRx))+","+strconv.Itoa(int(cache.LastTx)))
		}
	}
}
