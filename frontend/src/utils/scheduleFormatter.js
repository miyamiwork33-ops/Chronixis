import dayjs from "dayjs";

/**
 * 予定データを Chart.js 用に変換（24時間時計型）
 * @param {Array} schedules [{ start_time, end_time, activity, color }]
 * @param {Date|string} targetDate  // 例: Date または 'YYYY-MM-DD'
 * @param {Function} t  // 翻訳関数
 * @returns {Object} { labels, values, colors }
 */
export function formatSchedulesForChart(schedules, targetDate, t) {
  // 入力検証
  if (!schedules || !Array.isArray(schedules)) {
    return { labels: [], values: [], colors: [] };
  }

  if (!targetDate) {
    throw new Error("targetDate is required");
  }

  const result = {
    labels: [],
    values: [],
    colors: [],
  };

  // 予定を開始時間順に並べる
  const sorted = schedules
    .filter((item) => item.start_time && item.end_time)
    .map((item) => {
      const startTime = dayjs(`${targetDate} ${item.start_time}`);
      const endTime = dayjs(`${targetDate} ${item.end_time}`);

      let startMin = startTime.hour() * 60 + startTime.minute();
      let endMin = endTime.hour() * 60 + endTime.minute();

      // 日をまたぐ場合の処理
      if (endMin < startMin) {
        endMin = 1440; // 当日の終わりまでとする
      }

      return {
        ...item,
        startMin,
        endMin,
      };
    })
    .sort((a, b) => a.startMin - b.startMin);

  let lastEnd = 0;

  for (const item of sorted) {
    // 空白時間（未定義）
    if (item.startMin > lastEnd) {
      const gap = Math.min(item.startMin, 1440) - lastEnd;
      if (gap > 0) {
        result.labels.push(t("scheduleManage.undefined"));
        result.values.push(gap);
        result.colors.push("#000");
        lastEnd += gap;
      }
    }
    // オーバーラップを考慮して開始をクリップ
    const start = Math.max(item.startMin, lastEnd);
    const end = Math.max(start, item.endMin);
    const clampedEnd = Math.min(end, 1440);
    const duration = clampedEnd - start;
    if (duration > 0) {
      result.labels.push(item.activity);
      result.values.push(duration);
      result.colors.push(item.color || "#9E9E9E");
      lastEnd = clampedEnd;
    }
    if (lastEnd >= 1440) break;
  }

  if (lastEnd < 1440) {
    const gap = 1440 - lastEnd;
    result.labels.push(t("scheduleManage.undefined"));
    result.values.push(gap);
    result.colors.push("#000");
  }

  return result;
}
