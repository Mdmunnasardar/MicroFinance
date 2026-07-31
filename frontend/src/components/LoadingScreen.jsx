export default function LoadingScreen({ message = 'Loading…' }) {
  return (
    <div className="loading-screen">
      <div>
        <div className="spinner" style={{ margin: '0 auto 12px' }} />
        {message}
      </div>
    </div>
  );
}
