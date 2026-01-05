try {
  const dp = require('./node_modules/@vuepic/vue-datepicker/dist/vue-datepicker.js');
  console.log('Keys:', Object.keys(dp));
  console.log('Default:', dp.default ? 'Exists' : 'Missing');
  console.log('VueDatePicker:', dp.VueDatePicker ? 'Exists' : 'Missing');
} catch (e) {
  console.error(e);
}
