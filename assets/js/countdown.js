function initSwissCountdown() {
  const el = document.getElementById("swiss-countdown");
  if (!el || typeof swissCountdownData === "undefined") return;

  const end = parseInt(swissCountdownData.end || 0, 10); // unix seconds

  function tick() {
    if (!end || end <= 0) {
      el.innerHTML = "Not started";
      return;
    }

    const now = Math.floor(Date.now() / 1000);
    let remaining = end - now;

    if (remaining <= 0) {
      el.innerHTML = "⏰ Round ended!";
      return;
    }

    const mins = Math.floor(remaining / 60);
    const secs = remaining % 60;

    el.innerHTML = mins + "m " + (secs < 10 ? "0" : "") + secs + "s remaining";
    setTimeout(tick, 1000);
  }

  tick();
}

document.addEventListener("DOMContentLoaded", initSwissCountdown);
