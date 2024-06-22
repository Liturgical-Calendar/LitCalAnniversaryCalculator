const fs = require('fs').promises;

const readSourceData = async (lang = '') => {
    return await fs.readFile(`data/i18n/${lang}.json`, 'utf8');
}

const checkObjectKeys = async (lang = '') => {
    const itObjStr = await readSourceData('it');
    const itObj = JSON.parse(itObjStr);
    const langObjStr = await readSourceData(lang);
    const langObj = JSON.parse(langObjStr);

    Object.entries(itObj).forEach(([key,value]) => {
        Object.keys(value).forEach(key2 => {
            if(false === langObj.hasOwnProperty(key)) {
                langObj[key] = {};
            }
            if(false === langObj[key].hasOwnProperty(key2)) {
                langObj[key][key2] = '';
            }
        });
    });
    fs.writeFile(`data/i18n/${lang}.json`, JSON.stringify(langObj, null, 4), 'utf8');
}

['fr','es','de','pt','la'].forEach(lang => {
    checkObjectKeys(lang);
});
