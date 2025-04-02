using System;
using System.Collections.Generic;
using System.DirectoryServices.Protocols;
using System.Net;
using Microsoft.Extensions.Options;
using Models;

namespace Services
{
    public class LdapService : IDisposable, ILdapService
    {
        private readonly string _ldapServer;
        private readonly int _ldapPort;
        private readonly string _ldapDn;
        private readonly string _ldapUser;
        private readonly string _ldapPassword;
        private LdapConnection? _ldapConnection;

        public LdapService(IOptions<LdapSettings> options)
        {
            var settings = options.Value;
            _ldapServer = settings.Server;
            _ldapPort = settings.Port;
            _ldapDn = settings.Dn;
            _ldapUser = settings.User;
            _ldapPassword = settings.Password;
        }

        private LdapConnection Connect()
        {
            _ldapConnection = new LdapConnection(new LdapDirectoryIdentifier(_ldapServer, _ldapPort))
            {
                AuthType = AuthType.Basic
            };
            _ldapConnection.SessionOptions.ProtocolVersion = 3;
            _ldapConnection.SessionOptions.ReferralChasing = ReferralChasingOptions.None;
            return _ldapConnection;
        }

        public async Task<(bool, User?)> AuthenticateAsync(string username, string password)
        {
            var (result, userDetails) = await Task.Run(() => (AuthenticateUser(username, password, out var details), details));

            if (result)
            {
                User userModel = new User { SCE_USERNAME = username, SCE_FULLNAME = userDetails?["fullname"] ?? "" };
                return (result, userModel);
            }

            return (result, null);
        }

        public bool AuthenticateUser(string username, string password, out Dictionary<string, string>? userDetails)
        {
            userDetails = new Dictionary<string, string>();
            try
            {
                using var ldapConnection = Connect();

                // Intentar bind con usuario de servicio si es necesario
                if (!string.IsNullOrEmpty(_ldapUser) && !string.IsNullOrEmpty(_ldapPassword))
                {
                    ldapConnection.Bind(new NetworkCredential(_ldapUser, _ldapPassword));
                }
                else
                {
                    ldapConnection.Bind(); // Bind anónimo
                }

                // Escapar el nombre de usuario para evitar inyección LDAP
                string escapedUsername = System.Security.SecurityElement.Escape(username);
                string filter = $"(sAMAccountName={escapedUsername})";
                string[] attributes = { "distinguishedName", "cn", "givenName", "sn", "mail" };
                SearchRequest searchRequest = new SearchRequest(_ldapDn, filter, SearchScope.Subtree, attributes);
                var response = (SearchResponse)ldapConnection.SendRequest(searchRequest);

                if (response.Entries.Count == 0)
                {
                    Console.WriteLine("Usuario no encontrado en Active Directory.");
                    return false;
                }

                // Obtener DN del usuario
                string userDn = response.Entries[0].DistinguishedName;
                var entries = response.Entries[0].Attributes;

                // Autenticación del usuario con su propio DN
                using var userConnection = Connect();
                userConnection.Bind(new NetworkCredential(userDn, password));

                userDetails = new Dictionary<string, string>
                {
                    { "username", username },
                    { "fullname", entries["cn"]?[0]?.ToString() ?? "" },
                    { "email", entries.Contains("mail") ? entries["mail"][0]?.ToString() ?? "" : "" },
                    { "name", entries.Contains("givenName") ? entries["givenName"][0]?.ToString() ?? "" : "" },
                    { "surname", entries.Contains("sn") ? entries["sn"][0]?.ToString() ?? "" : "" }
                };

                return true;
            }
            catch (LdapException ldapEx)
            {
                Console.WriteLine($"Error LDAP: {ldapEx.Message}");
                return false;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Error de autenticación: {ex.Message}");
                return false;
            }
        }

        public void Dispose()
        {
            _ldapConnection?.Dispose();
        }
    }

    public class LdapSettings
    {
        public string Server { get; set; } = "";
        public int Port { get; set; } = 389;
        public string Dn { get; set; } = "";
        public string User { get; set; } = "";
        public string Password { get; set; } = "";
    }
}
