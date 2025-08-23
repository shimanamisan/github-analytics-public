FROM node:20.19-alpine

# 必要なパッケージをインストール
RUN apk add --no-cache procps

# # スクリプトをコンテナにコピー
# COPY increase_watchers.sh /usr/local/bin/increase_watchers.sh

# # スクリプトに実行権限を付与
# RUN chmod +x /usr/local/bin/increase_watchers.sh

# 作業ディレクトリを設定
WORKDIR /workspace/src