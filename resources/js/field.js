import DetailField from './components/DetailField'
import FormField from './components/FormField'

Nova.booting((app, store) => {
  app.component('detail-dependency-container', DetailField)
  app.component('form-dependency-container', FormField)
})
