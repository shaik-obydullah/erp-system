function App() {
  return (
    <div style={{
      minHeight: '100vh',
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      justifyContent: 'center',
      background: '#1a1a2e',
      color: 'white',
      fontFamily: 'sans-serif'
    }}>
      <h1 style={{ fontSize: '3rem', color: '#e94560', margin: 0 }}>
        Hello from React
      </h1>
      <p style={{ fontSize: '1.2rem', color: '#a0a0b0' }}>
        Running on port 3060
      </p>
    </div>
  )
}

export default App
