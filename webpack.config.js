const webpackAppConfig = require('./webpack.config.app');
const webpackEmbedProjects = require('./webpack.config.embed-projects');
const webpackEmbedEvents = require('./webpack.config.embed-events');
const webpackEmbedEducations = require('./webpack.config.embed-educations');
const webpackEmbedJobs = require('./webpack.config.embed-jobs');
const webpackEmbedRegions = require('./webpack.config.embed-regions');
const webpackEmbedPosts = require('./webpack.config.embed-posts');
const webpackEmbedContacts = require('./webpack.config.embed-contacts');
const webpackEmbedForms = require('./webpack.config.embed-forms');

// export the final configuration as an array of multiple configurations
module.exports = [
    ...webpackAppConfig,
    ...webpackEmbedProjects,
    ...webpackEmbedEvents,
    ...webpackEmbedEducations,
    ...webpackEmbedJobs,
    ...webpackEmbedRegions,
    ...webpackEmbedPosts,
    ...webpackEmbedContacts,
    ...webpackEmbedForms,
];
