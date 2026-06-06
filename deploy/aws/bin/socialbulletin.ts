import { App } from 'aws-cdk-lib';

import { SocialBulletinEnvironment } from '../lib/socialbulletin-environment.js';

const app = new App();
const environment = app.node.tryGetContext('environment') ?? 'preview';

new SocialBulletinEnvironment(app, environment);
