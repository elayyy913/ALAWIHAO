# Gemini chatbot setup

The chatbot searches the same approved article data used by `health_article.php`. It returns matching article excerpts and a link even when no API key is configured. To enable Gemini:

1. Create a Gemini API key in Google AI Studio.
2. Configure `GEMINI_API_KEY` and `GEMINI_MODEL` in Apache's server configuration, outside this repository and the web root. For XAMPP, you can add these inside the appropriate `<VirtualHost>` in `httpd-vhosts.conf` (or in the Apache configuration):

   ```apache
   SetEnv GEMINI_API_KEY "your-key"
   SetEnv GEMINI_MODEL "a-model-enabled-for-your-key"
   ```

3. Use a model name currently available to your Gemini API key, then restart Apache.

Never put the real key in PHP/JavaScript source, a `.env` file under `htdocs`, browser code, or Git. The PHP endpoint reads server environment values. It sends Gemini only the matched, approved article excerpts (or portal help context)—not the user's question or any patient, record, or appointment data. The local article excerpts remain available if Gemini is not configured or cannot be reached.
