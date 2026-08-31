export async function onRequestGet(context) {
  try {
    const { results } = await context.env.ARVORE_FAMILIA_DB.prepare(
      "SELECT * FROM visitas ORDER BY id DESC LIMIT 100"
    ).all();

    return new Response(JSON.stringify(results || []), {
      headers: { "Content-Type": "application/json" }
    });
  } catch (err) {
    return new Response(JSON.stringify({ error: err.message }), {
      status: 500,
      headers: { "Content-Type": "application/json" }
    });
  }
}
