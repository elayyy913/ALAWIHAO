# Gemini chatbot setup

The user chatbot searches the same approved article data used by `health_article.php`. The admin chatbot answers from approved portal guides for schedules and reschedule requests, registration approvals, patient and health records, health workers, vaccine inventory, SMS, activity history, and settings. Both have local guide answers when no API key is configured. To enable Gemini:

1. Create a Gemini API key in Google AI Studio.
2. Configure `GEMINI_API_KEY` and `GEMINI_MODEL` in Apache's server configuration, outside this repository and the web root. For XAMPP, you can add these inside the appropriate `<VirtualHost>` in `httpd-vhosts.conf` (or in the Apache configuration):

   ```apache
   SetEnv GEMINI_API_KEY "your-key"
   SetEnv GEMINI_MODEL "a-model-enabled-for-your-key"
   ```

3. Use a model name currently available to your Gemini API key, then restart Apache.

Never put the real key in PHP/JavaScript source, a `.env` file under `htdocs`, browser code, or Git. The PHP endpoints read server environment values. The user chatbot sends Gemini only matched, approved article excerpts or fixed portal help context—not the user's question or patient, record, or appointment data. The admin chatbot sends only a fixed admin guide context—not the admin's question or any patient, record, or appointment data. Local guide answers remain available if Gemini is not configured or cannot be reached.
