namespace Eduvixo.Windows.Models;

public sealed record AppSettings(string Language = "en", bool RememberChoice = false, string? Mode = null);
