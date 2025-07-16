import FieldListApp from './FieldListApp'

export default function AppRouter(props) {
  // For now, we'll just render the FieldListApp directly
  // In the future, this could handle routing between different pages
  return <FieldListApp {...props} />
}