export async function onRequestPost(context) {
  try {
    const body = await context.request.json();
    
    // Aqui fará a lógica para guardar os dados recebidos do frontend na tabela do D1.
    // Exemplo genérico de atualização por cada elemento:
    for (const id in body) {
      const m = body[id];
      await context.env.DB.prepare(`
        INSERT INTO membros (id, nome, naturalidade, matrimonio, obs, parent, filhos, status)
        VALUES (?1, ?2, ?3, ?4, ?5, ?6, ?7, ?8)
        ON CONFLICT(id) DO UPDATE SET
          nome = ?2, naturalidade = ?3, matrimonio = ?4, obs = ?5, parent = ?6, filhos = ?7, status = ?8
      `).bind(
        m.id, 
        m.nome, 
        m.naturalidade, 
        m.matrimonio, 
        m.obs, 
        m.parent || null, 
        JSON.stringify(m.filhos || []), 
        m.status || 'approved'
      ).run();
    }

    return new Response(JSON.stringify({ status: 'ok' }), {
      headers: { "Content-Type": "application/json" }
    });
  } catch (err) {
    return new Response(JSON.stringify({ status: 'error', error: err.message }), {
      status: 500,
      headers: { "Content-Type": "application/json" }
    });
  }
}
