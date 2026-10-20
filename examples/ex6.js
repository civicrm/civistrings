// Template literals, as minifiers write the strings of a bundled module
alert(ts(`Hello world`));
n(ts(`Don't say "%1"`,{1:e}),ts(`One \`thing\``,{domain:`myext`}));
E.ts(`%1 contact`,{1:n,count:n,plural:`%1 contacts`});
// A template with a placeholder is not a constant
alert(ts(`Hello ${name}`));
