export function getTextColor(hex) {
  if (!hex || typeof hex !== "string" || hex.length < 7) {
    throw new Error("Invalid hex color format. Expected format: #RRGGBB");
  }
  const r = parseInt(hex.slice(1, 3), 16);
  const g = parseInt(hex.slice(3, 5), 16);
  const b = parseInt(hex.slice(5, 7), 16);
  if (isNaN(r) || isNaN(g) || isNaN(b)) {
    throw new Error("Invalid hex color format. Expected format: #RRGGBB");
  }
  const luminance = 0.299 * r + 0.587 * g + 0.114 * b;
  return luminance > 186 ? "#000000" : "#FFFFFF";
}
