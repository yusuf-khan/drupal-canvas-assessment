// Use this Action for roles assigned from Auth0's Users > Roles tab.
exports.onExecutePostLogin = async (event, api) => {
  if (event.client.client_id !== 'REPLACE_WITH_THIS_APPLICATION_CLIENT_ID') {
    return;
  }

  const roles = event.authorization?.roles;
  const managedRoles = Array.isArray(roles)
    ? roles.filter((role) => typeof role === 'string')
    : [];

  api.idToken.setCustomClaim('https://sso-prototype.example/roles', managedRoles);
};
