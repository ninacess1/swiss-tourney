function renderStandings() {
  const el = document.getElementById("swiss-standings");
  if (!el || typeof swissStandingsData === "undefined") return;

  const players = swissStandingsData.players || [];

  if (!players.length) {
    el.innerHTML = `<h3>Current Standings</h3><p><em>No players yet.</em></p>`;
    return;
  }

  let html = `<h3>Current Standings</h3>
    <table class="swiss-standings">
      <thead>
        <tr>
          <th>Rank</th><th>First</th><th>Last</th><th>Player ID</th>
          <th>Status</th><th>W</th><th>L</th><th>D</th><th>Points</th>
        </tr>
      </thead><tbody>`;

  players.forEach((p, i) => {
    const status = (parseInt(p.dropped, 10) === 1) ? "Dropped" : "Active";
    html += `<tr>
      <td>${i + 1}</td>
      <td>${p.first_name || ""}</td>
      <td>${p.last_name || ""}</td>
      <td>${p.dci || ""}</td>
      <td>${status}</td>
      <td>${p.wins ?? 0}</td>
      <td>${p.losses ?? 0}</td>
      <td>${p.draws ?? 0}</td>
      <td>${p.points ?? 0}</td>
    </tr>`;
  });

  html += `</tbody></table>`;
  el.innerHTML = html;
}

document.addEventListener("DOMContentLoaded", renderStandings);
