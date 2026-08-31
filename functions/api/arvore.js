export async function onRequestGet(context) {
  try {
    const { results } = await context.env.ARVORE_FAMILIA_DB.prepare("SELECT * FROM membros").all();

    if (!results || results.length === 0) {
      return new Response(JSON.stringify({}), {
        headers: { "Content-Type": "application/json" }
      });
    }

    const treeData = {};

    // 1. Primeiro passo: popula todos os nós base
    results.forEach(row => {
      treeData[row.id] = {
        id: row.id,
        nome: row.nome || '',
        naturalidade: row.naturalidade || '',
        matrimonio: row.matrimonio || '',
        obs: row.obs || '',
        parent: row.parent || null,
        filhos: [],
        status: row.status || 'official'
      };
    });

    // 2. Segundo passo: reconstrói dinamicamente a árvore de filhos com base no campo 'parent'
    Object.values(treeData).forEach(member => {
      if (member.parent && treeData[member.parent]) {
        if (!treeData[member.parent].filhos.includes(member.id)) {
          treeData[member.parent].filhos.push(member.id);
        }
      }
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
