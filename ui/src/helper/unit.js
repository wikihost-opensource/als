export const formatBytes = (bytes, decimals = 2, bandwidth = false) => {
  if (bytes === 0) return '0 Bytes'

  let k = 1024
  const dm = decimals < 0 ? 0 : decimals
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']
  const bandwidthSizes = ['Bps', 'Kbps', 'Mbps', 'Gbps', 'Tbps', 'Pbs', 'Ebps', 'Zbps', 'Ybps']

  const i = Math.floor(Math.log(bytes) / Math.log(k))

  if (bandwidth) {
    let k = 1000
    bytes = bytes * 8
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + bandwidthSizes[i]
  }
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i]
}
