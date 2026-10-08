#include <WiFi.h>
#include <esp_now.h>
#include <esp_wifi.h>

const int RELAY_PIN = 5;
const int WIFI_CHANNEL = 1;
const uint8_t MEJA_ID = 1;
const char NAMA_MEJA[10] = "Meja1";

typedef struct __attribute__((packed)) {
  uint8_t  meja_id;
  uint8_t  perintah;
  uint16_t checksum;
} PerintahData;

typedef struct __attribute__((packed)) {
  uint8_t  meja_id;
  uint8_t  status;
  uint8_t  ack;
  char     nama[10];
} AckData;

bool relayOn = false;

void ensurePeer(const uint8_t *mac) {
  if (esp_now_is_peer_exist(mac)) return;

  esp_now_peer_info_t peerInfo = {};
  memcpy(peerInfo.peer_addr, mac, 6);
  peerInfo.channel = WIFI_CHANNEL;
  peerInfo.encrypt = false;
  esp_now_add_peer(&peerInfo);
}

void kirimAck(uint8_t status, uint8_t ack, const uint8_t *masterMac) {
  AckData data = {};
  data.meja_id = MEJA_ID;
  data.status = status;
  data.ack = ack;
  strncpy(data.nama, NAMA_MEJA, sizeof(data.nama) - 1);
  esp_now_send(masterMac, (uint8_t *)&data, sizeof(data));
}

void onDataReceive(const esp_now_recv_info_t *info, const uint8_t *data, int len) {
  if (len != sizeof(PerintahData)) return;

  PerintahData cmd;
  memcpy(&cmd, data, sizeof(cmd));

  if (cmd.meja_id != MEJA_ID) return;

  ensurePeer(info->src_addr);

  uint16_t expected = cmd.meja_id + cmd.perintah;
  if (cmd.checksum != expected) {
    kirimAck(relayOn ? 1 : 0, 0, info->src_addr);
    return;
  }

  relayOn = (cmd.perintah == 1);
  digitalWrite(RELAY_PIN, relayOn ? HIGH : LOW);
  kirimAck(relayOn ? 1 : 0, 1, info->src_addr);
}

void setup() {
  Serial.begin(115200);
  delay(500);

  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, LOW);

  WiFi.mode(WIFI_STA);
  WiFi.disconnect();
  esp_wifi_set_channel(WIFI_CHANNEL, WIFI_SECOND_CHAN_NONE);

  Serial.print("MAC address Client ini: ");
  Serial.println(WiFi.macAddress());
  Serial.println("-> Salin ke field 'Alamat MAC ESP32 Client' di clientMAC[] milik Master.");

  if (esp_now_init() != ESP_OK) {
    Serial.println("Gagal inisialisasi ESP-NOW");
    return;
  }
  esp_now_register_recv_cb(onDataReceive);

  Serial.printf("Client %s (meja_id=%d) siap, menunggu perintah dari Master...\n", NAMA_MEJA, MEJA_ID);
}

void loop() {
}
