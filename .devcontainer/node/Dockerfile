FROM node:18.16-buster

# 必要なパッケージをインストール
RUN apt-get update && \
    apt-get install -y procps && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# # スクリプトをコンテナにコピー
# COPY increase_watchers.sh /usr/local/bin/increase_watchers.sh

# # スクリプトに実行権限を付与
# RUN chmod +x /usr/local/bin/increase_watchers.sh

# 作業ディレクトリを設定
WORKDIR /workspace/src