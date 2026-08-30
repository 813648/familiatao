const fs = require('fs');
const { execSync } = require('child_process');

// 1. Ler o ficheiro da árvore validada
const rawData = fs.readFileSync('./arvore-validada.json', 'utf8');
const treeData = JSON.parse(rawData);

console.log("A iniciar a migração de pessoas para a Cloudflare D1...");

// 2. Iterar sobre cada pessoa na árvore
for (const [key, person] of Object.entries(treeData)) {
    const id = person.id || key;
    const nome = person.nome ? person.nome.replace(/'/g, "''") : '';
    const naturalidade = person.naturalidade ? person.naturalidade.replace(/'/g, "''") : '';
    const obs = person.obs ? person.obs.replace(/'/g, "''") : '';
    const parent = person.parent || '';
    const status = person.status || 'official';
    const matrimonio = person.matrimonio ? person.matrimonio.replace(/'/g, "''") : '';

    // Construir a query SQL de inserção
    const sql = `INSERT OR REPLACE INTO pessoas (id, nome, naturalidade, obs, parent_id, status, matrimonio) VALUES ('${id}', '${nome}', '${naturalidade}', '${obs}', '${parent}', '${status}', '${matrimonio}');`;

    // Executar via Wrangler D1 no terminal
    try {
        const command = `npx wrangler d1 execute arvore-familia-db --command="${sql}" --local=false`;
        execSync(command, { stdio: 'inherit' });
        console.log(`Pessoa inserida/atualizada com sucesso: ${nome} (${id})`);
    } catch (error) {
        console.error(`Erro ao inserir a pessoa ${id}:`, error.message);
    }
}

console.log("Migração concluída com sucesso!");
