using System.Globalization;
using System.Reflection;
using System.Text.Json;
using Eduvixo.Windows.Models;

namespace Eduvixo.Windows.Services;

public static class LocalizationService
{
    public static IReadOnlyList<LanguageOption> Languages { get; } =
    [
        new("zh", "中文"),
        new("en", "English"),
        new("de", "Deutsch"),
        new("lo", "ລາວ"),
        new("pl", "Polski"),
        new("th", "ไทย"),
        new("vi", "Tiếng Việt")
    ];

    private static readonly Dictionary<string, string> English = LoadDictionary("en");
    private static Dictionary<string, string> _current = English;

    public static string CurrentCode { get; private set; } = "en";

    public static string DetectSystemLanguage()
    {
        var code = CultureInfo.CurrentUICulture.TwoLetterISOLanguageName.ToLowerInvariant();
        return IsSupported(code) ? code : "en";
    }

    public static bool IsSupported(string? code) => Languages.Any(language => language.Code == code);

    public static void SetLanguage(string code)
    {
        CurrentCode = IsSupported(code) ? code : "en";
        _current = CurrentCode == "en" ? English : LoadDictionary(CurrentCode);
    }

    public static string Get(string key)
    {
        if (_current.TryGetValue(key, out var value))
        {
            return value;
        }

        return English.TryGetValue(key, out value) ? value : key;
    }

    public static string BrowserCulture(string code) => code switch
    {
        "zh" => "zh-CN",
        "de" => "de-DE",
        "lo" => "lo-LA",
        "pl" => "pl-PL",
        "th" => "th-TH",
        "vi" => "vi-VN",
        _ => "en-US"
    };

    private static Dictionary<string, string> LoadDictionary(string code)
    {
        try
        {
            var assembly = Assembly.GetExecutingAssembly();
            var suffix = $".lang.{code}.json";
            var resourceName = assembly.GetManifestResourceNames().Single(name => name.EndsWith(suffix, StringComparison.OrdinalIgnoreCase));
            using var stream = assembly.GetManifestResourceStream(resourceName);
            return stream is null
                ? new Dictionary<string, string>(StringComparer.Ordinal)
                : JsonSerializer.Deserialize<Dictionary<string, string>>(stream) ?? new Dictionary<string, string>(StringComparer.Ordinal);
        }
        catch
        {
            return new Dictionary<string, string>(StringComparer.Ordinal);
        }
    }
}
