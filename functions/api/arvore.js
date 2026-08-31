export async function onRequestGet(context) {
  try {
    // Assume que a ligação à base de dados D1 no painel do Cloudflare Pages se chama 'DB'
    const { results } = await context.env.ARVORE_FAMILIA_DB.prepare("SELECT * FROM membros").all();

    // Se a tabela estiver vazia, pode devolver um objeto vazio ou uma estrutura inicial
    if (!results || results.length === 0) {
      return new Response(JSON.stringify({}), {
        headers: { "Content-Type": "application/json" }
      });
    }

    // Se guardar os dados como um objeto chave-valor na BD ou se precisar de os formatar:
    // (Ajuste conforme a estrutura exata das suas colunas no D1)
    const treeData = {};
    results.forEach(row => {
      treeData[row.id] = {
        id: row.id,
        nome: row.nome,
        naturalidade: row.naturalidade,
        matrimonio: row.matrimonio,
        obs: row.obs,
        parent: row.parent,
        filhos: row.filhos ? JSON.parse(row.filhos) : [],
        status: row.status
      };
    });

    return new Response(JSON.stringify(treeData), {
      headers: { "Content-Type": "application/json" }
    });
  } catch (err) {
    return new Response(JSON.stringify({ error: err.message }), {
      status: 500,
      headers: { "Content-Type": "application/json" }
    });
  }
}
