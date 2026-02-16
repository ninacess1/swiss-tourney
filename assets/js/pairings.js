function renderPairings() {
  const el = document.getElementById("swiss-pairings");
  if (!el || typeof swissPairingsData === "undefined") return;

  const { round, pairings } = swissPairingsData;
  let html = `<h3>Round ${round || ""} Pairings</h3>
    <table class="swiss-pairings">
      <thead><tr><th>Table</th><th>Player A</th><th>Player B</th></tr></thead>
      <tbody>`;

  pairings.forEach(p => {
    const playerA = `${p.a_first || ""} ${p.a_last || ""} (${p.a_dci || ""})`;
    const playerB = p.b_first ? `${p.b_first || ""} ${p.b_last || ""} (${p.b_dci || ""})` : "BYE";

    html += `<tr>
      <td>${p.table_no}</td>
      <td>${playerA}</td>
      <td>${playerB}</td>
    </tr>`;
  });

  html += `</tbody></table>`;
  el.innerHTML = html;
}
document.addEventListener("DOMContentLoaded", renderPairings);
