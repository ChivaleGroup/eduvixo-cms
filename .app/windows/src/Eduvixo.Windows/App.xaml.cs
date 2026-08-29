using System.IO;
using System.Threading;
using System.Windows;
using System.Windows.Threading;

namespace Eduvixo.Windows;

public partial class App : Application
{
    private Mutex? _instanceMutex;
    private bool _ownsInstanceMutex;

    protected override void OnStartup(StartupEventArgs e)
    {
        _instanceMutex = new Mutex(true, "Local\\Eduvixo.Windows", out _ownsInstanceMutex);
        if (!_ownsInstanceMutex)
        {
            MessageBox.Show("Eduvixo is already running.", "Eduvixo", MessageBoxButton.OK, MessageBoxImage.Information);
            Shutdown();
            return;
        }

        DispatcherUnhandledException += OnDispatcherUnhandledException;
        base.OnStartup(e);
    }

    protected override void OnExit(ExitEventArgs e)
    {
        if (_instanceMutex is not null && _ownsInstanceMutex)
        {
            _instanceMutex.ReleaseMutex();
            _ownsInstanceMutex = false;
        }

        _instanceMutex?.Dispose();

        base.OnExit(e);
    }

    private static void OnDispatcherUnhandledException(object sender, DispatcherUnhandledExceptionEventArgs e)
    {
        try
        {
            var directory = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "Eduvixo", "Logs");
            Directory.CreateDirectory(directory);
            File.AppendAllText(Path.Combine(directory, "application.log"), $"{DateTimeOffset.Now:O} {e.Exception}\n");
        }
        catch
        {
        }

        MessageBox.Show("Eduvixo encountered an unexpected error. Restart the application and try again.", "Eduvixo", MessageBoxButton.OK, MessageBoxImage.Error);
        e.Handled = true;
    }
}
