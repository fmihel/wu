/* eslint-disable no-plusplus */
const FtpDeploy = require('ftp-deploy');
const { config, paths } = require('./ftp.config');

const ftp = new FtpDeploy();

const line = (msg, len = 60) => {
    let lin = '';
    for (let i = 0; i < len - msg.length; i++) lin += '-';
    console.log(`deploy ${msg} ${lin}`);
};
line(`to: ${paths.remote.render}`);

ftp
    .deploy(config)
    .then((res) => {
        console.log(res);
        console.log('result: ok.');
        line('stop ');
    })
    .catch((err) => {
        console.error(err);
        line('stop ');
    });
