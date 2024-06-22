const fs = require('fs').promises;

let itObj = null;
let enObj = null;

const readSourceData = async (lang = '') => {
    return await fs.readFile(`data/i18n/${lang}.json`, 'utf8');
}

const elaborateSourceData = async () => {
    const itObjStr = await readSourceData('it');
    itObj = JSON.parse(itObjStr);
    const enObjStr = await readSourceData('en');
    enObj = JSON.parse(enObjStr);

    let untranslatedEnStrings = {};

    Object.entries(itObj).forEach(([key,value]) => {
        Object.entries(value).forEach(([key2,value2]) => {
            if(false === enObj.hasOwnProperty(key)) {
                console.log(`enObj does not have key <${key}>`);
                return;
            }
            if(false === enObj[key].hasOwnProperty(key2)) {
                console.log(`enObj[${key}] does not have key <${key2}>`);
                return;
            }
            if(value2 !== '' && enObj[key][key2] === '') {
                if(false === untranslatedEnStrings.hasOwnProperty(key)) {
                    untranslatedEnStrings[key] = {};
                }
                untranslatedEnStrings[key][key2] = '';
            }
        });
    });

    console.log(untranslatedEnStrings);
}

elaborateSourceData();
