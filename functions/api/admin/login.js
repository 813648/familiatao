export async function onRequestPost(context) {
  try {
    const { password } = await context.request.json();
    const ADMIN_PASSWORD = context.env.ADMIN_PASSWORD || "12345";

    if (password === ADMIN_PASSWORD) {
      return new Response(JSON.stringify({ success: true, token: "admin_auth_token_ok" }), {
        headers: { "Content-Type": "application/json" }
      });
    } else {
      return new Response(JSON.stringify({ success: false, error: "Password incorreta." }), {
        status: 401,
        headers: { "Content-Type": "application/json" }
      });
    }
  } catch (err) {
    return new Response(JSON.stringify({ success: false, error: err.message }), {
      status: 500,
      headers: { "Content-Type": "application/json" }
    });
  }
}
