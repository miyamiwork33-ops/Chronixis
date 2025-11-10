# Chronixis — Personal Productivity Suite

[![Chronixis Logo](https://chronixis.miyamin.com/image/logo.png)](https://chronixis.miyamin.com)

**デモサイト:** [https://chronixis.miyamin.com](https://chronixis.miyamin.com)

Chronixis は「習慣化」「予定」「日記」「TODO」「データ分析」を  
シンプルかつ直感的に操作できるパーソナル管理ツールです。  
視覚的に分かりやすい UI（円グラフ・カンバン・ガントチャート）を備え、  
将来的なモバイル連携やチーム機能も見据えた設計になっています。

## 目次
- 概要
- 主要機能
- 技術スタック
- 操作説明

## 概要
- バックエンド: Laravel（API）
- フロントエンド: Vue 3 + Vite（SPA）
- 認証: Laravel Sanctum（トークン認証）
- コンテナ化: Docker / docker-compose

用途: 個人の習慣・予定管理、日記、TODO 管理、データ分析を行うためのフルスタックアプリ。業務系の要件に近い設計で、モデル・マイグレーション・API とフロントの連携を示しています。

## 主要機能
- **ダッシュボード** — 各機能への入口・当日の状況を俯瞰
- **カレンダー** — イベント・予定の登録と可視化
- **スケジュール管理** — 一日の行動時間を円形に配置した形式で確認
- **習慣トラッカー** — 目標を設定し進捗を可視化
- **日記** — Markdown対応、日々の記録を残す
- **データ分析** — 円グラフやガントチャートによる可視化
- **カンバン** — タスクをドラッグ&ドロップで整理
- **タスク管理** — 状態（ToDo・進行中・完了）で整理可能

## 技術スタック
| 分類 | 技術 | 補足 |
|------|------|------|
| Backend | **Laravel 11 (API構成)** | REST APIを提供、SanctumによるSPA認証 |
| Frontend | **Vue 3 + Vite** | Composition API / Primevue CSS利用 |
| Database | **MySQL** | Docker Volumeで永続化 |
| Auth | **Laravel Breeze + Sanctum** | SPAトークン認証を実装 |
| Container | **Docker / Docker Compose** | PHP, Vue, MySQL, phpMyAdminを構成 |

## 操作説明

![Chronixis Logo](https://chronixis.miyamin.com/image/logo.png)

![Chronixis Schedule](https://chronixis.miyamin.com/image/schedule.png)
*1日の行動を円形で可視化するスケジュール登録画面*

![Chronixis Habit](https://chronixis.miyamin.com/image/habit.png)
*習慣トラッカー画面（目標達成率の可視化）*

現在は簡易的なデモのみを提供していますが、今後、操作マニュアルサイトを整備予定です。  
チュートリアル形式で各機能の使用方法を掲載予定です。

## その他
**ライセンス:** 
このコードは自己紹介・技術力の紹介を目的として公開しています。  
BSD 3-Clauseライセンスのもとで利用可能ですが、著作権表示の保持をお願いします。  
商用利用や再配布を行う際は、事前にご連絡いただけると幸いです。  
詳細は LICENSE ファイルをご確認ください。

**最終更新:** 2025-11-10