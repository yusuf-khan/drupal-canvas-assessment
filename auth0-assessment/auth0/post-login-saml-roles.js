// Attach to the Post Login flow after configuring a separate SAML application.
exports.onExecutePostLogin = async (event, api) => {
  if (
    event.client.client_id !== 'REPLACE_WITH_SAML_APPLICATION_CLIENT_ID' ||
    event.transaction?.protocol !== 'samlp'
  ) {
    return;
  }

  const roles = event.authorization?.roles;
  const roleNames = Array.isArray(roles)
    ? roles.filter((role) => typeof role === 'string')
    : [];

  api.samlResponse.setAttribute('https://sso-prototype.example/roles', roleNames);
};
