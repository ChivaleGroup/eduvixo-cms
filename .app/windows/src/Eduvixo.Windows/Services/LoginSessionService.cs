using System.Net;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Text.Json;
using System.Text.Json.Serialization;
using System.Text.RegularExpressions;
using Eduvixo.Windows.Models;

namespace Eduvixo.Windows.Services;

public sealed partial class LoginSessionService : IDisposable
{
    private static readonly Uri BaseUri = new("https://demo.eduvixo.com/");
    private readonly CookieContainer _cookies = new();
    private readonly HttpClient _client;
    private string _csrf = string.Empty;

    public LoginSessionService()
    {
        var handler = new HttpClientHandler
        {
            AllowAutoRedirect = false,
            CookieContainer = _cookies,
            UseCookies = true
        };

        _client = new HttpClient(handler)
        {
            BaseAddress = BaseUri,
            Timeout = TimeSpan.FromSeconds(20)
        };
        _client.DefaultRequestHeaders.UserAgent.ParseAdd("Eduvixo-Windows/0.2.0");
    }

    public async Task<LoginBootstrap> PrepareAsync(string language, CancellationToken cancellationToken = default)
    {
        using var request = new HttpRequestMessage(HttpMethod.Get, "login");
        request.Headers.CacheControl = new CacheControlHeaderValue { NoCache = true, NoStore = true };
        request.Headers.AcceptLanguage.ParseAdd(LocalizationService.BrowserCulture(language));
        using var response = await _client.SendAsync(request, HttpCompletionOption.ResponseContentRead, cancellationToken);
        response.EnsureSuccessStatusCode();

        var html = await response.Content.ReadAsStringAsync(cancellationToken);
        _csrf = InputValue(html, "csrf");
        if (_csrf.Length != 64)
        {
            throw new InvalidOperationException("The login security token is unavailable.");
        }

        var captchaEnabled = InputExists(html, "captcha");
        var captcha = captchaEnabled ? await DownloadCaptchaAsync(false, cancellationToken) : null;
        return new LoginBootstrap(InputValue(html, "email"), InputValue(html, "password"), captchaEnabled, captcha);
    }

    public Task<byte[]> RefreshCaptchaAsync(CancellationToken cancellationToken = default) =>
        DownloadCaptchaAsync(true, cancellationToken);

    public async Task<LoginResult> LoginAsync(string email, string password, string captcha, CancellationToken cancellationToken = default)
    {
        using var request = new HttpRequestMessage(HttpMethod.Post, "login")
        {
            Content = new FormUrlEncodedContent(new Dictionary<string, string>
            {
                ["csrf"] = _csrf,
                ["email"] = email.Trim(),
                ["password"] = password,
                ["captcha"] = captcha.Trim()
            })
        };
        request.Headers.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));
        request.Headers.Add("X-Eduvixo-Request", "1");
        request.Headers.Referrer = new Uri(BaseUri, "login");

        using var response = await _client.SendAsync(request, HttpCompletionOption.ResponseContentRead, cancellationToken);
        var json = await response.Content.ReadAsStringAsync(cancellationToken);
        var result = JsonSerializer.Deserialize<LoginResponse>(json);
        if (result is null)
        {
            throw new InvalidOperationException("The login response is invalid.");
        }

        return new LoginResult(response.IsSuccessStatusCode && result.Ok, result.Message ?? string.Empty);
    }

    public IReadOnlyList<Cookie> SessionCookies() =>
        _cookies.GetCookies(BaseUri).Cast<Cookie>().ToArray();

    public void Dispose() => _client.Dispose();

    private async Task<byte[]> DownloadCaptchaAsync(bool refresh, CancellationToken cancellationToken)
    {
        var path = $"captcha/login.png?app={DateTimeOffset.UtcNow.ToUnixTimeMilliseconds()}";
        if (refresh)
        {
            path += "&refresh=1";
        }

        using var request = new HttpRequestMessage(HttpMethod.Get, path);
        request.Headers.CacheControl = new CacheControlHeaderValue { NoCache = true, NoStore = true };
        using var response = await _client.SendAsync(request, HttpCompletionOption.ResponseContentRead, cancellationToken);
        response.EnsureSuccessStatusCode();
        return await response.Content.ReadAsByteArrayAsync(cancellationToken);
    }

    private static bool InputExists(string html, string name) =>
        InputElementRegex().Matches(html).Cast<Match>().Any(match =>
            string.Equals(AttributeValue(match.Value, "name"), name, StringComparison.OrdinalIgnoreCase));

    private static string InputValue(string html, string name)
    {
        var input = InputElementRegex().Matches(html).Cast<Match>().FirstOrDefault(match =>
            string.Equals(AttributeValue(match.Value, "name"), name, StringComparison.OrdinalIgnoreCase));
        return input is null ? string.Empty : WebUtility.HtmlDecode(AttributeValue(input.Value, "value"));
    }

    private static string AttributeValue(string element, string attribute)
    {
        foreach (Match match in AttributeRegex().Matches(element))
        {
            if (string.Equals(match.Groups["name"].Value, attribute, StringComparison.OrdinalIgnoreCase))
            {
                return match.Groups["value"].Value;
            }
        }

        return string.Empty;
    }

    [GeneratedRegex("<input\\b[^>]*>", RegexOptions.IgnoreCase | RegexOptions.CultureInvariant)]
    private static partial Regex InputElementRegex();

    [GeneratedRegex("(?<name>[a-z_:][-a-z0-9_:.]*)\\s*=\\s*[\\\"'](?<value>.*?)[\\\"']", RegexOptions.IgnoreCase | RegexOptions.CultureInvariant)]
    private static partial Regex AttributeRegex();

    private sealed record LoginResponse(
        [property: JsonPropertyName("ok")] bool Ok,
        [property: JsonPropertyName("message")] string? Message);
}
