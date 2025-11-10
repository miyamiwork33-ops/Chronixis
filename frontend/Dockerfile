# Debian ベース
FROM node:22-slim

# 作業ディレクトリを/appに設定
WORKDIR /app

# 依存関係の定義ファイルをコピー
COPY package*.json ./

# 依存関係をインストール
RUN npm ci && npm cache clean --force

# ソースコードをすべてコピー
COPY . .

EXPOSE 5173

# Vueの開発サーバーをポート5173で起動
CMD ["npm", "run", "dev"]
