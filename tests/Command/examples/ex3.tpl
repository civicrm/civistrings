<p>{ts}First{/ts}</p>
<p>{ts escape="js"}Second{/ts}</p>
<p>{ts 1=$crmURL}You can <a href='%1'>create one here</a>.{/ts}</p>
<li>{ts 1=$siteRoot 2='civicrm/profile/create?reset=1&amp;gid=3'}<strong>Contact Signup Forms</strong> - Create link(s) to 'new contact' input form(s) for your Profiles using the following path: <em>%1/%2</em>. (This example links to an input form for Profile ID 3.){/ts}</li>
{ts count=$unMatchCount plural='CiviCRM has detected mismatched membership IDs. These records have not been Updated.'}CiviCRM has detected mismatched membership ID. This record has not been updated.{/ts}
