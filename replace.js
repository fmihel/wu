/* eslint-disable array-callback-return */
/* eslint-disable no-throw-literal */
const fs = require('fs');
const path = require('path');
const { exit } = require('process');
/**  replace in files:
 *  ex: node replace [<config>] ( default config = replace.config.json)
 *  config json format:
 *  {
 *      "list":[
 *          {
 *              "files":["file1","file2",...],
 *              "rules":[
 *                  {"search":"search string","replace":"new string"},
 *                  ...
 *              ]
 *          },
 *          ...
 *      ]
 *      "deleteFiles":[
 *          ".dist/*.json",..
 *      ]
 * }
 *
*/
/** список файлов в папке используя фильтр regexp */
const getFilesByFilter = (startPath, regexFilter, deep, callback) => {
    if (fs.existsSync(startPath)) {
        const files = fs.readdirSync(startPath);
        for (let i = 0; i < files.length; i++) {
            const filename = path.join(startPath, files[i]);
            const stat = fs.lstatSync(filename);
            if (deep && stat.isDirectory()) {
                getFilesByFilter(filename, regexFilter, true, callback); // recurse
            } else if (regexFilter.test(filename)) callback(filename);
        }
    }
};

/** преобразует фильтр * в регуляное выражение, для использования в getFiles */
const maskToRegEx = (str) => {
    let out = str;
    out = out.split('.').join('\\.');
    out = out.split('*').join('\\S*');
    return new RegExp(`${out}$`);
};

/** разделяет имяфайла на путь и имя */
const fileExt = (filename) => {
    const name = path.basename(filename);
    let dir = path.dirname(filename);
    if (dir === '.') dir = './';
    if (dir === '..') dir = '../';
    return { name, dir };
};

const getFiles = (name, deep = false) => {
    const file = fileExt(name);
    const files = [];
    getFilesByFilter(file.dir, maskToRegEx(file.name), deep, (filename) => {
        files.push(filename);
    });
    return files;
};

const args = process.argv.slice(2);
const configFileName = args[0] || './replace.config.json';
// eslint-disable-next-line no-underscore-dangle
const _line = (text = '', len = 30) => {
    const out = text ? `- ${text} ` : text;
    console.log(out + '-'.repeat(len - out.length));
};

const replace = (inFile, rules = []) => {
    let data = fs.readFileSync(inFile, { encoding: 'utf8', flag: 'r' });
    rules.map((rule) => {
        console.log(`  search:"${rule.search}"`, `,replace:"${rule.replace}"`);
        data = data.split(rule.search).join(rule.replace);
    });
    fs.writeFileSync(inFile, data, { encoding: 'utf8' });
};

async function main() {
    if (!fs.existsSync(configFileName)) {
        throw `config file [${configFileName}] is not exists`;
    }

    const configJSON = fs.readFileSync(configFileName);
    const config = JSON.parse(configJSON);

    config.list.map((it) => {
        it.files.map((name) => {
            const files = getFiles(name);
            files.map((file) => {
                console.log(`  change in "${file}"`);
                replace(file, it.rules);
            });
        });
    });

    if (config.deleteFiles && config.deleteFiles.length) {
        _line('delete');

        config.deleteFiles.map((name) => {
            const files = getFiles(name);
            files.map((filename) => {
                fs.unlinkSync(filename);
                console.log(`  deleted "${filename}"`);
            });
        });
    }

    return true;
}
_line('replace');
main()
    .then(() => {
        _line('replace end ok');
    }).catch((e) => {
        console.error(e);
        _line('replace end with error!');
    });
