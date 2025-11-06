#!/bin/bash
set -euo pipefail

APP_DIR="/home/bot/front_builder/git"
BUILD_COPY_TO="/home/bot/front"
BUILD_COPY_TO2="/home/tgbotbuyss/frontend/front"
SUB_FOLDER="frontend-basic"
SUB_FOLDER2="frontend-updated"
REPO_URL="https://github.com/ng-asror/korolevskiy_gaz_2.git"

BOT_TOKEN="7016194666:AAH9fThidorjd5HoPIKA8zhQc2GHIPxRfd0"
CHAT_ID="-4922484355"

# === Telegramga xabar yuborish funksiyasi ===
send_message() {
    local TEXT="$1"
    curl -s -X POST "https://api.telegram.org/bot${BOT_TOKEN}/sendMessage" \
         -d chat_id="${CHAT_ID}" \
         -d text="$TEXT" \
         >/dev/null
}	

send_message "${REPO_URL}%0A%0A🔄 Yangi deploy jarayoni boshlandi..."

npm install -g pnpm

# === Deploy jarayoni ===
cd "$APP_DIR" || { echo "❌ APP_DIR mavjud emas"; exit 1; }

send_message "🚀 Deploy boshlandi..."

# Repo mavjudligini tekshirish
if [ ! -d ".git" ]; then
    send_message "📥 Git clone qilinyapti..."
    git clone "$REPO_URL" . || { send_message "❗️ Git clone xatosi"; exit 1; }
else
    echo "🔄 Repo mavjud, yangilanmoqda..."
fi

# Kode yangilash
send_message "🔄 Kod yangilanmoqda..."
git fetch --all
git reset --hard origin/main

if [ "$SUB_FOLDER" != "" ]; then
    send_message "🔄 Ish boshlandi $SUB_FOLDER"
    cd "$SUB_FOLDER"
fi 

# Paketlar
send_message "📦 Paketlar o‘rnatilmoqda..."
pnpm install --frozen-lockfile || { send_message "❗️ Paketlarni o‘rnatishda xato"; exit 1; }

# Build
send_message "🏗 Build qilinyapti..."
pnpm build || { send_message "❗️ Build xato"; exit 1; }

# Old files ni o‘chirish (ichidagini tozalaymiz, katalogning o‘zi qoladi)
rm -rf "$BUILD_COPY_TO"/* || { send_message "❗️ Old files ni o‘chirishda xato"; exit 1; }

# Keyin yangi buildni ko‘chirish
cp -r dist/frontend/browser/* "$BUILD_COPY_TO"/ || { send_message "❗️ Build fayllarini ko‘chirishda xato"; exit 1; }

# Telegramga xabar yuborish
send_message "✅ botpl.ru - Deploy yakunlandi!"


if [ "$SUB_FOLDER2" != "" ]; then
    send_message "🔄 Ish boshlandi $SUB_FOLDER2"
    cd ..
    cd "$SUB_FOLDER2"
fi 

# Paketlar
send_message "📦 Paketlar o‘rnatilmoqda..."
pnpm install --frozen-lockfile || { send_message "❗️ Paketlarni o‘rnatishda xato"; exit 1; }

# Build
send_message "🏗 Build qilinyapti..."
pnpm build || { send_message "❗️ Build xato"; exit 1; }

# Old files ni o‘chirish (ichidagini tozalaymiz, katalogning o‘zi qoladi)
rm -rf "$BUILD_COPY_TO2"/* || { send_message "❗️ Old files ni o‘chirishda xato"; exit 1; }

# Keyin yangi buildni ko‘chirish
cp -r dist/frontend/browser/* "$BUILD_COPY_TO2"/ || { send_message "❗️ Build fayllarini ko‘chirishda xato"; exit 1; }

# Telegramga xabar yuborish
send_message "✅ tgbotbuyss.ru - Deploy yakunlandi!"
