#include <esp_now.h>
#include <WiFi.h>
#include <esp_wifi.h>
#include <WebSocketsClient.h>

const char* ssid     = "NAMA_WIFI_TOKO";
const char* password = "PASSWORD_WIFI";

const char* websocket_server = "192.168.1.100";
const uint16_t websocket_port = 81;
const char* websocket_path = "/";

#define NUM_MEJA 2

uint8_t clientMAC[NUM_MEJA][6] = {
  {0x68, 0xFE, 0x71, 0x16, 0xA6, 0x5C},
  {0x3C, 0x71, 0xBF, 0x7E, 0x34, 0x0C}
};

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

PerintahData cmdData;
AckData ackData;

bool statusMeja[NUM_MEJA] = {};

WebSocketsClient webSocket;

String serialBuffer = "";

void onDataRecv(const esp_now_recv_info_t *recv_info, const uint8_t *incomingData, int len) {
  memcpy(&ackData, incomingData, sizeof(ackData));

  Serial.print("[ESP-NOW] ACK diterima dari ");
  Serial.print(ackData.nama);
  Serial.print(" | Status Relay: ");
  Serial.print(ackData.status == 1 ? "ON" : "OFF");
  Serial.print(" | ACK: ");
  Serial.println(ackData.ack == 1 ? "SUKSES" : "GAGAL");

  if (ackData.meja_id >= 1 && ackData.meja_id <= NUM_MEJA) {
    statusMeja[ackData.meja_id - 1] = (ackData.status == 1);
  }

  String ackMsg = "ACK," + String(ackData.meja_id) + "," +
                  String(ackData.status) + "," + String(ackData.ack) + "," +
                  String(ackData.nama);
  webSocket.sendTXT(ackMsg);

  Serial.print("[WEB] Kirim ke PC: ");
  Serial.println(ackMsg);
}

void onDataSent(const wifi_tx_info_t *info, esp_now_send_status_t status) {
  Serial.print("[ESP-NOW] Status kirim perintah: ");
  Serial.println(status == ESP_NOW_SEND_SUCCESS ? "Sukses" : "Gagal");
}

void kirimPerintah(uint8_t mejaID, uint8_t perintah) {
  if (mejaID < 1 || mejaID > NUM_MEJA) {
    Serial.println("[ERROR] ID Meja tidak valid!");
    return;
  }

  cmdData.meja_id = mejaID;
  cmdData.perintah = perintah;
  cmdData.checksum = mejaID + perintah;

  uint8_t *targetMAC = clientMAC[mejaID - 1];

  Serial.print("[MASTER] Mengirim perintah ke Meja ");
  Serial.print(mejaID);
  Serial.print(": ");
  Serial.println(perintah == 1 ? "ON" : "OFF");

  esp_err_t result = esp_now_send(targetMAC, (uint8_t *)&cmdData, sizeof(cmdData));

  if (result == ESP_OK) {
    Serial.println("[MASTER] Perintah berhasil masuk antrean kirim.");
    String feedback = "SEND," + String(mejaID) + "," + String(perintah) + ",OK";
    webSocket.sendTXT(feedback);
  } else {
    Serial.println("[MASTER] Gagal mengirim perintah!");
    String feedback = "SEND," + String(mejaID) + "," + String(perintah) + ",FAIL";
    webSocket.sendTXT(feedback);
  }
}

void prosesPerintah(String input) {
  input.trim();
  input.toUpperCase();

  Serial.print("[CMD] Perintah diterima: ");
  Serial.println(input);

  if (input == "STATUS") {
    String statusMsg = "STATUS";
    for (int i = 0; i < NUM_MEJA; i++) {
      Serial.print("Meja ");
      Serial.print(i + 1);
      Serial.print(": ");
      Serial.println(statusMeja[i] ? "ON" : "OFF");
      statusMsg += "," + String(statusMeja[i] ? "1" : "0");
    }
    webSocket.sendTXT(statusMsg);
    return;
  }

  if (input.startsWith("ALL,")) {
    String cmd = input.substring(4);
    uint8_t val = (cmd == "ON") ? 1 : 0;

    for (int i = 1; i <= NUM_MEJA; i++) {
      kirimPerintah(i, val);
      delay(50);
    }
    return;
  }

  if (input.startsWith("M") && input.indexOf(",") > 0) {
    int commaIndex = input.indexOf(",");
    String idStr = input.substring(1, commaIndex);
    String cmd = input.substring(commaIndex + 1);

    int mejaID = idStr.toInt();
    uint8_t val = (cmd == "ON") ? 1 : 0;

    if (mejaID >= 1 && mejaID <= NUM_MEJA) {
      kirimPerintah(mejaID, val);
    } else {
      Serial.println("[ERROR] ID Meja di luar jangkauan NUM_MEJA!");
    }
    return;
  }

  Serial.println("[ERROR] Format perintah tidak dikenali!");
}

void webSocketEvent(WStype_t type, uint8_t * payload, size_t length) {
  switch (type) {
    case WStype_DISCONNECTED:
      Serial.println("[WEB] Terputus dari bridge");
      break;
    case WStype_CONNECTED:
      Serial.println("[WEB] Terhubung ke bridge");
      webSocket.sendTXT("MASTER_ONLINE");
      break;
    case WStype_TEXT:
      {
        String pesan = String((char*)payload).substring(0, length);
        Serial.print("[WEB] Pesan dari bridge: ");
        Serial.println(pesan);
        prosesPerintah(pesan);
      }
      break;
    case WStype_BIN:
      Serial.println("[WEB] Data biner diterima (tidak diproses)");
      break;
    case WStype_ERROR:
      Serial.println("[WEB] Error pada WebSocket");
      break;
    default:
      break;
  }
}

void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.print("Menghubungkan ke WiFi ");
  Serial.print(ssid);
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();
  Serial.print("WiFi terhubung, IP: ");
  Serial.println(WiFi.localIP());

  esp_wifi_set_ps(WIFI_PS_NONE);
  esp_wifi_set_channel(WiFi.channel(), WIFI_SECOND_CHAN_NONE);
  Serial.print("Channel WiFi: ");
  Serial.println(WiFi.channel());

  if (esp_now_init() != ESP_OK) {
    Serial.println("[ERROR] Gagal inisialisasi ESP-NOW!");
    return;
  }

  esp_now_register_recv_cb(onDataRecv);
  esp_now_register_send_cb(onDataSent);

  Serial.printf("Mendaftarkan %d Client sebagai peer...\n", NUM_MEJA);
  for (int i = 0; i < NUM_MEJA; i++) {
    esp_now_peer_info_t peerInfo = {};
    memcpy(peerInfo.peer_addr, clientMAC[i], 6);
    peerInfo.channel = WiFi.channel();
    peerInfo.encrypt = false;

    if (esp_now_add_peer(&peerInfo) == ESP_OK) {
      Serial.print("  [OK] Meja ");
      Serial.print(i + 1);
      Serial.print(" -> MAC: ");
      for (int j = 0; j < 6; j++) {
        if (j > 0) Serial.print(":");
        Serial.printf("%02X", clientMAC[i][j]);
      }
      Serial.println();
    } else {
      Serial.print("  [FAIL] Meja ");
      Serial.print(i + 1);
      Serial.println(" gagal didaftarkan!");
    }
  }

  Serial.println("Menghubungkan ke bridge WebSocket...");
  webSocket.begin(websocket_server, websocket_port, websocket_path);
  webSocket.onEvent(webSocketEvent);
  webSocket.setReconnectInterval(5000);

  Serial.println("[OK] ESP32 Master siap!");
}

void loop() {
  webSocket.loop();

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[WIFI] Terputus, mencoba reconnect...");
    WiFi.reconnect();
    delay(1000);
  }

  while (Serial.available()) {
    char c = Serial.read();

    if (c == '\n') {
      prosesPerintah(serialBuffer);
      serialBuffer = "";
    } else if (c != '\r') {
      serialBuffer += c;
    }
  }

  delay(10);
}
